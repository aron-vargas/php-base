<style>
    .form-signin {
        max-width: 600px;
        padding: 15px;
    }

    .card-body {
        border: 1px solid #CCC;
        border-radius: 8px;
    }
</style>
<div role='main' class='container'>
    <main class='form-signin w-100 m-auto'>
        <div class="card-body p-md-5 mx-md-4">
            <form action='/membership/signup/save' method='POST'>
                <h4>Register</h4>
                <div class="mb-4">
                    <label class="form-label" for="company_name" style="margin-left: 0px;">Company Name</label>
                    <input type="text" id="company_name" name="company_name" class="form-control"
                        placeholder="Your Company's Name" />
                </div>
                <div class="mb-4">
                    <label class="form-label" for="email" style="margin-left: 0px;">Email</label>
                    <input type="email" id="email" name="email" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="phone" style="margin-left: 0px;">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="(000) 000 0000">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="website" style="margin-left: 0px;">Business Website</label>
                    <input type="text" id="website" name="website" class="form-control" placeholder="www.domain.com">
                </div>
                <div class="mb-4">
                    <div>Business Type <span class="helper-text">(check all that apply)</span></div>
                    <div>
                        <input type="checkbox" id="focus-truck" name="focus-truck" class="form-check" />
                        <label for="focus-truck">Food Truck</label>
                    </div>
                    <div>
                        <input type="checkbox" id="focus-catering" name="focus-catering" class="form-check" />
                        <label for="focus-catering">Catering</label>
                    </div>
                    <div>
                        <input type="checkbox" id="focus-chef" name="focus-chef" class="form-check" />
                        <label for="focus-chef">Private Chef</label>
                    </div>
                    <div>
                        <input type="checkbox" id="focus-bakery" name="focus-bakery" class="form-check" />
                        <label for="focus-bakery">Bakery</label>
                    </div>
                    <div>
                        <input type="checkbox" id="focus-foodprep" name="focus-foodprep" class="form-check" />
                        <label for="focus-foodprep">Small Batch Food Production</label>
                    </div>
                    <div>
                        <input type="checkbox" id="focus-mealprep" name="focus-mealprep" class="form-check" />
                        <label for="focus-mealprep">Meal Prep / Meal Kit Services</label>
                    </div>
                    <div>
                        <input type="checkbox" id="focus-ghost" name="focus-ghost" class="form-check" />
                        <label for="focus-ghost">Ghost Kitchen</label>
                    </div>
                    <div>
                        <input type="checkbox" id="focus-other" name="focus-other" class="form-check" />
                        <label for="focus-other">Other</label>
                        <input type="text" id="focus-other-text" name="focus-other-text" class="form-control">
                    </div>
                </div>
                <div class="mb-4">
                    <div>Space I will need <span class="helper-text">(check all that apply)</span></div>
                    <div>
                        <input type="checkbox" id="space-dry" name="space-dry" class="form-check" />
                        <label for="space-dry">Dry Stoarge</label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-refrigerator" name="space-refrigerator" class="form-check" />
                        <label for="space-refrigerator">Refrigerator Storage</label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-refrigerator" name="space-refrigerator" class="form-check" />
                        <label for="space-refrigerator">Freezer Storage</label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-drivebay" name="space-drivebay" class="form-check" />
                        <label for="space-drivebay">Access to the drive-bay for fulfillment of orders</label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-greese" name="space-greese" class="form-check" />
                        <label for="space-greese">Access to grease trap services</label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-dinning" name="space-dinning" class="form-check" />
                        <label for="space-dinning">Access to dinning room</label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-bar" name="space-bar" class="form-check" />
                        <label for="space-bar">Access to bar area</label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-parking" name="space-parking" class="form-check" />
                        <label for="space-parking">FOOD TRUCKS: Overnight parking</label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-greywater" name="space-greywater" class="form-check" />
                        <label for="space-greywater">FOOD TRUCKS: Grey water dump site</label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-waterrefill" name="space-waterrefill" class="form-check" />
                        <label for="space-waterrefill">FOOD TRUCKS: Potable water refill station</label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-drivebay" name="space-drivebay" class="form-check" />
                        <label for="space-drivebay">FOOD TRUCKS: Access to the drive-bay <span
                                class="helper-text">(Maintenance and cleaning)</span></label>
                    </div>
                    <div>
                        <input type="checkbox" id="space-other" name="space-other" class="form-check" />
                        <label for="space-other">Other</label>
                        <input type="text" id="space-other-text" name="space-other-text" class="form-control">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="num_hours" style="margin-left: 0px;">How many hours per week do you
                        need the kitchen?</label>
                    <input type="text" id="num_hours" name="num_hours" class="form-control" />
                </div>
                <div class="mb-4">
                    <label class="form-label" for="num_days" style="margin-left: 0px;">How many days of the week do you
                        need the kitchen?</label>
                    <input type="text" id="num_days" name="num_days" class="form-control" placeholder="www.domain.com">
                </div>
                <div class="mb-4">
                    <div>Paperwork checklist! Which of the following items do you have?</div>
                    <div>
                        <input type="checkbox" id="checklist-foodsafety" name="checklist-foodsafety"
                            class="form-check" />
                        <label for="checklist-foodsafety">Food Safety Manager Certificate</label>
                    </div>
                    <div>
                        <input type="checkbox" id="checklist-insurance" name="checklist-insurance" class="form-check" />
                        <label for="checklist-insurance">Business Liability Insurance</label>
                    </div>
                    <div>
                        <input type="checkbox" id="checklist-healthpermit" name="checklist-healthpermit" class="form-check" />
                        <label for="checklist-healthpermit">Nevada County / Placer County Environmental Health Permit</label>
                    </div>
                    <div>
                        <input type="checkbox" id="checklist-sellerpermit" name="checklist-sellerpermit"
                            class="form-check" />
                        <label for="checklist-sellerpermit">CA Seller's Permit</label>
                    </div>
                </div>
                <div class="mb-4">
                    <label>Any Additional Information:</label>
                    <textarea id='comments' name='comments' cols='50' rows='10'></textarea>
                </div>
                <div class="text-center pt-1 mb-4 pb-1">
                    <button type='submit' class="btn btn-primary" type="button" onClick="SubmitFrom(this)">Submit</button>
                </div>
            </form>
        </div>
    </main>
</div>